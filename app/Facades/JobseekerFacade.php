<?php
/**
 * Created by PhpStorm.
 * User: andrestntx
 * Date: 3/24/16
 * Time: 9:03 AM
 */

namespace App\Facades;


use App\Entities\Job;
use App\Entities\Jobseeker;
use App\Entities\Resume;
use App\Entities\User;
use App\Services\ApplicationService;
use App\Services\EmailService;
use App\Services\ExperienceService;
use App\Services\GeoLocationService;
use App\Services\JobseekerService;
use App\Services\JobService;
use App\Services\OccupationService;
use App\Services\ProfileService;
use App\Services\ResumeService;
use App\Services\StudyService;
use Illuminate\Database\Eloquent\Model;

class JobseekerFacade
{
    /**
     * @var ResumeService
     */
    protected $resumeService;

    /**
     * @var JobseekerService
     */
    protected $jobseekerService;

    /**
     * @var GeoLocationService
     */
    protected $geoLocationService;

    /**
     * @var StudyService
     */
    protected $studyService;

    /**
     * @var ExperienceService
     */
    protected $experienceService;

    /**
     * @var ApplicationService
     */
    protected $applicationService;

    /**
     * @var EmailService
     */
    protected $emailService;

    /**
     * @var ProfileService
     */
    protected $profileService;


    /**
     * JobseekerFacade constructor.
     * @param ResumeService $resumeService
     * @param JobseekerService $jobseekerService
     * @param StudyService $studyService
     * @param GeoLocationService $geoLocationService
     * @param ExperienceService $experienceService
     * @param ApplicationService $applicationService
     * @param EmailService $emailService
     * @param OccupationService $occupationService
     * @param ProfileService $profileService
     */
    public function __construct(ResumeService $resumeService, JobseekerService $jobseekerService,
                                StudyService $studyService, GeoLocationService $geoLocationService,
                                ExperienceService $experienceService, ApplicationService $applicationService,
                                EmailService $emailService, OccupationService $occupationService, ProfileService $profileService)
    {
        $this->resumeService = $resumeService;
        $this->occupationService = $occupationService;
        $this->jobseekerService = $jobseekerService;
        $this->studyService = $studyService;
        $this->experienceService = $experienceService;
        $this->applicationService = $applicationService;
        $this->emailService = $emailService;
        $this->geoLocationService = $geoLocationService;
        $this->profileService = $profileService;
    }

    /**
     * @param Jobseeker $jobseeker
     * @return string
     */
    public function getPhoto(Jobseeker $jobseeker)
    {
        return $this->jobseekerService->getPhotoUrl($jobseeker);
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function createJobseeker(array $data)
    {
        $data = $this->geoLocationService->validAndMerge($data);
        $jobseeker = $this->jobseekerService->createModel($data);
        $this->jobseekerService->validAndSavePhoto($data, $jobseeker);

        return $jobseeker;
    }

    /**
     * @param array $data
     * @param Model $jobseeker
     * @return mixed
     */
    protected function updateJobseeker(array $data, Model $jobseeker)
    {
        $this->jobseekerService->validAndSavePhoto($data, $jobseeker);
        $data = $this->geoLocationService->validAndMerge($data);
        return $this->jobseekerService->updateModel($data, $jobseeker);
    }

    /**
     * @param array $newStudies
     * @param Resume $resume
     */
    protected function addNewStudies(array $newStudies = null, Resume $resume)
    {
        if(! is_null($newStudies)) {
            $newStudies = $this->studyService->getNewStudies($newStudies);
            $this->resumeService->addNewStudies($resume, $newStudies);
        }
    }

    /**
     * @param array|null $newExperiences
     * @param Resume $resume
     */
    protected function addNewExperiences(array $newExperiences = null, Resume $resume)
    {
        if(! is_null($newExperiences)) {
            $newExperiences = $this->experienceService->getNewExperiences($newExperiences);
            $this->resumeService->addNewExperiences($resume, $newExperiences);
        }
    }

    /**
     * @param array|null $studies
     */
    protected function updateStudies(array $studies = null)
    {
        if(! is_null($studies)) {
            $this->studyService->updateStudies($studies);
        }
    }

    /**
     * @param array|null $experiences
     */
    protected function updateExperiences(array $experiences = null)
    {
        if(! is_null($experiences)) {
            $this->experienceService->updateExperiences($experiences);
        }
    }

    /**
     * @param array $dataResume
     * @param array $newStudies
     * @param array $newExperiences
     * @return mixed
     */
    public function createResume(array $dataResume, array $newStudies = null, array $newExperiences = null)
    {
        $dataResume = $this->geoLocationService->validAndMerge($dataResume);
        $jobseeker  = $this->createJobseeker($dataResume);
        $newResume  = $this->resumeService->newModel($dataResume);
        $resume     = $this->jobseekerService->addNewResume($jobseeker, $newResume);

        $this->addNewStudies($newStudies, $resume);
        $this->addNewExperiences($newExperiences, $resume);
        $this->resumeService->validAndSaveResumeFile($dataResume, $resume);

        return $resume;
    }

    /**
     * @param Resume $resume
     * @param array $dataResume
     * @param array $newStudies
     * @param array $studies
     * @param array $newExperiences
     * @param array $experiences
     * @return mixed
     */
    public function updateResume(Resume $resume, array $dataResume, array $newStudies = null, array $studies = null,
                                    array $newExperiences = null, array $experiences = null)
    {
        $dataResume = $this->geoLocationService->validAndMerge($dataResume);
        $this->updateJobseeker($dataResume, $resume->jobseeker);

        $this->addNewStudies($newStudies, $resume);
        $this->updateStudies($studies);

        $this->addNewExperiences($newExperiences, $resume);
        $this->updateExperiences($experiences);

        $resume = $this->resumeService->updateModel($dataResume, $resume);
        $this->resumeService->validAndSaveResumeFile($dataResume, $resume);
        $this->resumeService->validAndSaveVaccinesFile($dataResume, $resume);

        return $resume;
    }

    /**
     * @param null $occupationId
     * @param null $profileId
     * @param int $experience
     * @param null $search
     * @return array
     */
    public function searchResumes($occupationId = null, $profileId = null, $experience = 0, $search = null)
    {
        $occupation = $this->occupationService->getModel($occupationId);
        $profile = $this->profileService->getModel($profileId);

        return $this->resumeService->getSearchResumes($occupation, $profile, $experience, $search);
    }

    public function applyJob(Job $job, array $data)
    {
        $resume         = $this->resumeService->getAuthResume();
        $application    = $this->applicationService->applyJob($resume, $job, $data);
        //$pathResume     = $this->resumeService->getResumeFile($resume);
        //$this->emailService->sendResume($resume, $job, $application, $pathResume);

        return $application;
    }

    /**
     * @param User $user
     * @param Job $job
     * @return int
     */
    public function countApplications(User $user, Job $job)
    {
        $resume = $this->resumeService->getResume($user);

        if($resume) {
            return $this->applicationService->count($resume, $job);
        }

        return 0;
    }

    public function getApplications(Resume $resume)
    {
        return $this->applicationService->getOfResume($resume);
    }

    /**
     * @param Resume $resume
     * @return string
     */
    public function hasPdf(Resume $resume)
    {
        return $this->resumeService->hasPdf($resume);
    }

    /**
     * @param Resume $resume
     * @return string
     */
    public function getResumeFile(Resume $resume)
    {
        return $this->resumeService->getFile($resume);
    }
}